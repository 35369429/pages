/**
 * 文章列表页
 */
import React, { Component } from 'react';
import { TabNavigator, StackNavigator } from 'react-navigation';
import {
  Platform,
  StyleSheet,
  SectionList,
  RefreshControl,
  StatusBar,
  Text,
  Image,
  View
} from 'react-native';
import { observer } from 'mobx-react';
import FocuseSwiper from '../../components/FocusSwiper';
import ArticleItem from '../../components/ArticleItem';
import xpm from '../../xpm';
import { UltimateListView, UltimateRefreshView } from 'react-native-ultimate-listview';

import {color, css} from 'skin/pages/Home';

type Props = {
    api:string,
}


@observer
export default class ArticleList extends React.Component {
  props: Props;
  constructor(props, context) {
      super(props, context);
      this.state = {
        articles: []
      };
  }

  /**
   * 读取本页数据
   */
  onFetch = async (page = 1, startFetch, abortFetch, props) => {
    // console.log( 'onFetch', page, props );
    try {
      let cid = props.category_id;
      let tpl = props.page || 'default';
      let navigate = props.navigate;
      let $search = xpm.api('/xpmsns/pages/article/search');
      let articles = await $search().get({page:page, perpage:15, categoryId:cid, order:'publish_time desc'});

      for ( var i in articles.data ) {
        articles.data[i]['tpl'] = tpl;
        articles.data[i]['navigate'] = navigate;
      }
      console.log( 'onFetch', {page:page, perpage:15, categoryId:cid, order:'publish_time desc'},  articles.data );
      startFetch(articles.data, articles.perpage  );
    } catch ( excp ) {
        abortFetch();
        console.log( 'something error', excp );
    }
  }

  /**
   * 渲染 Item
   */
  renderItem( item, index) {

    return (<ArticleItem
              navigate={item.navigate}
              article_id={item.article_id}
              title={item.title}
              publish_time={item.publish_time}
              publish_date={item.publish_date}
              author={item.author}
              summary={item.summary}
              tpl={item.tpl}
              cover={item.cover || {}}
              images={item.images || []}
              videos={item.videos || []}
              thumbs={item.thumbs || []} />)
  }

  render() {


     // console.log('List render');
     const curr = this.props.data.curr;
     if ( curr !== this.props.curr ) {
       return null;
     }

     // return (<View style={css.bgWhite}><Text style={css.bgWhite}>HELLOW</Text></View>);
     return (

       <UltimateListView
         keyExtractor={(item, index) => `${index}${item.category_id}`} //this is required when you are using FlatList
         refreshableMode="advanced" //basic or advanced
         onFetch={(page, startFetch, abortFetch)=>{this.onFetch(page, startFetch, abortFetch, this.props ); } }
         item={this.renderItem}  //this takes two params (item, index)
         numColumns={1} //to use grid layout, simply set gridColumn > 1

         // 参数调试
         // refreshable={false}
         // autoPagination={false}

         paginationFetchingView={()=>{ console.log('first fetch'); return null; }}
         refreshableTitleRefreshing="刷新中,请稍候..."
         refreshableTitlePull="下拉刷新"
         refreshableTitleRelease="放手更新"
         waitingSpinnerText="加载更多,请稍候..."
         allLoadedText="没有更多了"

         arrowImageStyle={{ width: 20, height: 20, resizeMode: 'contain' }}
         refreshViewStyle={Platform.OS === 'ios' ? { height: 80, top: -80 } : { height: 80 }}
         refreshViewHeight={80}

       />
     );
   }
 }
